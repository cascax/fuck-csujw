import numpy as np
import imgdata as data

def writeTheta(theta):
    f = open('h.txt', 'w')
    for x in theta:
        for y in x:
            f.write(str(y)+' ')
        f.write('\n')
    f.close()

def readTheta():
    hmat = []
    f = open('h.txt')
    for line in f.readlines():
        lineArr = line.strip().split()
        hmat.append([float(x) for x in lineArr])
    f.close()
    return np.mat(hmat)

def sigmoid(inX):
    return 1.0 / (1.0 + np.exp(-inX))

def h(theta, x):
    h = x * theta
    return sigmoid(h)

def trainLogisticRegression(trainX, trainY, alpha, maxIter):
    numSamples, numFeatures = np.shape(trainX)
    theta = np.ones((numFeatures,1))

    for x in range(maxIter):
        output = h(theta, trainX)
        error = output - trainY
        # alpha = 5.0 / (100.0 + x) + 0.005;
        theta -= alpha * np.mat(trainX).T * error
        # c = cost(output, trainY)
        # if c<0.01:
        #     print 'break le o~'
        #     break
        # print c
    return theta

def cost(pre, exp):
    c = 0
    for p,e in zip(np.array(pre.T)[0],np.array(exp.T)[0]):
        if e==1:
            c -= np.log(p)
        else:
            c -= np.log(1-p)
    return c


def train(n=800):
    hmat = []
    for char in data.category:
        target = []
        for t in data.target:
            if t==char:
                target += [1]
            else:
                target += [0]
        target = np.array([target]).T

        c = trainLogisticRegression(data.images[:n], target[:n], 0.01, 400)
        hmat.append(c.T[0])

    # write trained theta to file
    hmat = np.array(hmat).T
    writeTheta(hmat)

if __name__ == '__main__':
    train(2000)

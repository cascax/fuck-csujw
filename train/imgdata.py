import numpy as np

def readImageData():
    f = open('data.txt')
    images = []
    for line in f.readlines():
        lineArr = [float(x) for x in line.strip().split()]
        images.append(lineArr)
    f.close()
    return np.mat(images)

def readTarget():
    f = open('target.txt')
    target = ''
    for line in f.readlines():
        target += line.strip()
    f.close()
    return list(target)

images = readImageData()
target = readTarget()
category = list('0123456789abcdefghigklmnopqrstuvwxyz')
